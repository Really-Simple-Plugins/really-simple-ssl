import * as React from "react";
const SvgPaPanama = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="PA_-_Panama_svg__a"
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
    <g mask="url(#PA_-_Panama_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="PA_-_Panama_svg__b"
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
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#PA_-_Panama_svg__b)">
        <path
          fill="#E31D1C"
          d="M8 0v6h8V0H8ZM11.51 9.575l-1.15.712.446-1.196-.978-.905h1.187l.495-1.294.379 1.294h1.189l-.857.905.42 1.196-1.13-.712Z"
        />
        <path
          fill="#2E42A5"
          d="m4.51 4.182-1.15.713.446-1.196-.978-.905h1.187L4.51 1.5l.379 1.294h1.189l-.857.905.42 1.196-1.13-.713ZM0 6v6h8V6H0Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgPaPanama;
