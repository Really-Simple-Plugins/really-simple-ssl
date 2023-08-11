import * as React from "react";
const SvgUaUkraine = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="UA_-_Ukraine_svg__a"
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
    <g mask="url(#UA_-_Ukraine_svg__a)">
      <path
        fill="#3195F9"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="UA_-_Ukraine_svg__b"
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
      <g mask="url(#UA_-_Ukraine_svg__b)">
        <path
          fill="#FECA00"
          fillRule="evenodd"
          d="M0 6v6h16V6H0Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgUaUkraine;
