import * as React from "react";
const SvgSrSuriname = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="SR_-_Suriname_svg__a"
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
    <g mask="url(#SR_-_Suriname_svg__a)">
      <path
        fill="#4E8B1D"
        fillRule="evenodd"
        d="M0 8h16v4H0V8ZM0 0h16v3H0V0Z"
        clipRule="evenodd"
      />
      <path
        fill="#AF0100"
        stroke="#fff"
        strokeWidth={1.5}
        d="M0 3.25h-.75v5.5h17.5v-5.5H0Z"
      />
      <path
        fill="#FECA00"
        fillRule="evenodd"
        d="M8.001 7.247 6.754 8l.285-1.47L6 5.432l1.406-.06L8.001 4l.595 1.372H10L8.964 6.53 9.276 8 8 7.247Z"
        clipRule="evenodd"
      />
    </g>
  </svg>
);
export default SvgSrSuriname;
