import * as React from "react";
const SvgKpKoreaNorth = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="KP_-_Korea_(North)_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#KP_-_Korea_(North)_svg__a)">
      <path
        fill="#3D58DB"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="KP_-_Korea_(North)_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#KP_-_Korea_(North)_svg__b)">
        <path fill="#C51918" stroke="#F7FCFF" d="M0 2.5h-.5v7h17v-7H0Z" />
        <path
          fill="#F7FCFF"
          fillRule="evenodd"
          d="M5 8.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"
          clipRule="evenodd"
        />
        <path
          fill="#C51918"
          fillRule="evenodd"
          d="m4.998 6.935-1.359.945.48-1.584-1.319-1 1.654-.033.544-1.563.543 1.563 1.654.034-1.318 1 .479 1.583-1.358-.945Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgKpKoreaNorth;
